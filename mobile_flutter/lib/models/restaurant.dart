class Restaurant {
  const Restaurant({
    required this.id,
    required this.name,
    required this.description,
    required this.eventDate,
    required this.averagePrice,
    required this.latitude,
    required this.longitude,
    required this.contactName,
    required this.contactEmail,
    required this.photo,
    required this.status,
    required this.createdBy,
    this.ownerId,
    this.rejectionReason,
    this.ownerName,
  });

  final int id;
  final String name;
  final String description;
  final String eventDate;
  final int averagePrice;
  final double latitude;
  final double longitude;
  final String contactName;
  final String contactEmail;
  final String photo;
  final String status;
  final int createdBy;
  final int? ownerId;
  final String? rejectionReason;
  final String? ownerName;

  factory Restaurant.fromJson(Map<String, dynamic> json) {
    return Restaurant(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: (json['name'] ?? '') as String,
      description: (json['description'] ?? '') as String,
      eventDate: (json['event_date'] ?? '') as String,
      averagePrice: (json['average_price'] as num?)?.toInt() ?? 0,
      latitude: double.tryParse('${json['latitude'] ?? 0}') ?? 0,
      longitude: double.tryParse('${json['longitude'] ?? 0}') ?? 0,
      contactName: (json['contact_name'] ?? '') as String,
      contactEmail: (json['contact_email'] ?? '') as String,
      photo: ((json['photo'] ?? json['image']) ?? '') as String,
      status: (json['status'] ?? 'accepted') as String,
      createdBy: (json['created_by'] as num?)?.toInt() ?? 0,
      ownerId: (json['owner_id'] as num?)?.toInt(),
      rejectionReason: json['rejection_reason'] as String?,
      ownerName: json['owner_name'] as String?,
    );
  }
}
